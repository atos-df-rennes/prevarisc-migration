<?php

namespace App\Entity;

use App\Repository\GroupeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: GroupeRepository::class)]
class Groupe
{
    /**
     * @var int
     */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'ID_GROUPE', type: 'bigint', options: ['unsigned' => true])]
    private $id;

    #[Assert\NotBlank(message: 'Cette valeur ne doit pas être vide.')]
    #[ORM\Column(name: 'LIBELLE_GROUPE', type: 'string', length: 255, nullable: false)]
    private ?string $libelle = null;

    #[ORM\Column(name: 'DESC_GROUPE', type: 'text', nullable: true)]
    private ?string $description = null;

    /**
     * @var Collection<int, Utilisateur>
     */
    #[ORM\OneToMany(targetEntity: Utilisateur::class, mappedBy: 'groupe')]
    private ArrayCollection|array $utilisateurs;

    /**
     * @var Collection<int, Privileges>
     */
    #[ORM\JoinTable(name: '`groupe-privileges`')]
    #[ORM\JoinColumn(name: 'ID_GROUPE', referencedColumnName: 'ID_GROUPE', options: ['unsigned' => true])]
    #[ORM\InverseJoinColumn(name: 'id_privilege', referencedColumnName: 'id_privilege', options: ['unsigned' => true])]
    #[ORM\ManyToMany(targetEntity: Privileges::class, inversedBy: 'groupes')]
    private ArrayCollection|array $privileges;

    /**
     * @var Collection<int, News>
     */
    #[ORM\JoinTable(name: 'newsgroupe')]
    #[ORM\JoinColumn(name: 'ID_GROUPE', referencedColumnName: 'ID_GROUPE', options: ['unsigned' => true])]
    #[ORM\InverseJoinColumn(name: 'ID_NEWS', referencedColumnName: 'ID_NEWS', options: ['unsigned' => true])]
    #[ORM\ManyToMany(targetEntity: News::class)]
    private ArrayCollection|array $news;

    public function __construct()
    {
        $this->utilisateurs = new ArrayCollection();
        $this->privileges = new ArrayCollection();
        $this->news = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLibelle(): ?string
    {
        return $this->libelle;
    }

    public function setLibelle(string $libelle): self
    {
        $this->libelle = $libelle;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @return Collection<int, Utilisateur>
     */
    public function getUtilisateurs(): Collection
    {
        return $this->utilisateurs;
    }

    public function addUtilisateur(Utilisateur $utilisateur): self
    {
        if (!$this->utilisateurs->contains($utilisateur)) {
            $this->utilisateurs[] = $utilisateur;
            $utilisateur->setGroupe($this);
        }

        return $this;
    }

    public function removeUtilisateur(Utilisateur $utilisateur): self
    {
        // set the owning side to null (unless already changed)
        if ($this->utilisateurs->removeElement($utilisateur) && $utilisateur->getGroupe() === $this) {
            $utilisateur->setGroupe(null);
        }

        return $this;
    }

    /**
     * @return Collection<int, Privileges>
     */
    public function getPrivileges(): Collection
    {
        return $this->privileges;
    }

    public function addPrivilege(Privileges $privilege): self
    {
        if (!$this->privileges->contains($privilege)) {
            $this->privileges[] = $privilege;
        }

        return $this;
    }

    public function removePrivilege(Privileges $privilege): self
    {
        $this->privileges->removeElement($privilege);

        return $this;
    }

    /**
     * @return Collection<int, News>
     */
    public function getNews(): Collection
    {
        return $this->news;
    }

    public function addNews(News $news): self
    {
        if (!$this->news->contains($news)) {
            $this->news[] = $news;
        }

        return $this;
    }

    public function removeNews(News $news): self
    {
        $this->news->removeElement($news);

        return $this;
    }
}
