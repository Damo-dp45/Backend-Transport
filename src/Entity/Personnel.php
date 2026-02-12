<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\OpenApi\Model\Operation;
use App\Entity\Interface\EntrepriseOwnedInterface;
use App\Entity\Interface\HasSoftDeleteGuard;
use App\Repository\PersonnelRepository;
use App\State\PersonnelProcessor;
use App\State\SoftDeleteProcessor;
use App\State\UpdatedbyProcessor;
use App\Validator\UniquePerEntreprise;
use ArrayObject;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: PersonnelRepository::class)]
#[UniquePerEntreprise(
    fields: ['nom' ,'prenom', 'contact', 'typepersonnel'],
    message: 'Le personnel existe déjà dans votre entreprise'
)] # On peut un 'code' par entreprise mais prendre en compte le 'deletedAt'
#[ApiResource(
    security: "is_granted('IS_AUTHENTICATED_FULLY')",
    normalizationContext: ['groups' => ['read:Personnel', 'read:Base']],
    denormalizationContext: ['groups' => ['write:Personnel']],
    paginationEnabled: false,
    order: ['createdAt' => 'DESC'],
    operations: [
        new GetCollection(
            security: "is_granted('VOIR', 'Personnel')",
            openapi: new Operation(
                summary: 'La liste des personnels',
                description: 'Permet de voir la liste des personnels',
                security: [['bearerAuth' => []]]
            )
        ),
        new Get(
            security: "is_granted('VOIR', object)",
            requirements: ['id' => '\d+'],
            openapi: new Operation(
                summary: 'Le personnel',
                description: 'Permet de voir un personnel',
                security: [['bearerAuth' => []]]
            )
        ),
        new Post(
            security: "is_granted('CREER', 'Personnel')",
            processor: PersonnelProcessor::class,
            openapi: new Operation(
                summary: 'Création d\'un personnel',
                description: 'Permet de créer un personnel',
                security: [['bearerAuth' => []]]
            )
        ),
        new Patch(
            security: "is_granted('MODIFIER', object)",
            requirements: ['id' => '\d+'],
            processor: UpdatedbyProcessor::class,
            openapi: new Operation(
                summary: 'Modification d\'un personnel',
                description: 'Permet de modifier un personnel',
                security: [['bearerAuth' => []]]
            )
        ),
        new Patch(
            security: "is_granted('SUPPRIMER', object)",
            uriTemplate: '/personnels/{id}/remove',
            requirements: ['id' => '\d+'],
            input: false,
            processor: SoftDeleteProcessor::class,
            openapi: new Operation(
                summary: 'Mise en corbeille d\'un personnel',
                description: 'Permet de mettre un personnel en corbeille',
                security: [['bearerAuth' => []]]
            )
        )
    ],
    openapi: new Operation(
        security: [['bearerAuth' => []]]
    )
)]
class Personnel extends EntityBase implements EntrepriseOwnedInterface // HasSoftDeleteGuard
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['read:Depannage', 'read:Voyage', 'read:Personnel', 'read:Trajet'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['read:Depannage', 'read:Voyage', 'read:Personnel', 'write:Personnel', 'read:Trajet'])]
    #[Assert\NotBlank]
    #[Assert\Length(min: 2)]
    private ?string $nom = null;

    #[ORM\Column(length: 255)]
    #[Groups(['read:Depannage', 'read:Voyage', 'read:Personnel', 'write:Personnel', 'read:Trajet'])]
    #[Assert\NotBlank]
    #[Assert\Length(min: 2)]
    private ?string $prenom = null;

    #[ORM\Column(length: 255)]
    #[Groups(['read:Depannage', 'read:Voyage', 'read:Personnel', 'write:Personnel', 'read:Trajet'])]
    #[Assert\NotBlank]
    #[Assert\Length(min: 3)]
    private ?string $contact = null;

    #[ORM\Column(length: 255)]
    #[Groups(['read:Depannage', 'read:Voyage', 'read:Personnel', 'read:Trajet'])]
    private ?string $code = null;

    #[ORM\ManyToOne(inversedBy: 'personnels')]
    #[ORM\JoinColumn(nullable: false)] // onDelete: 'RESTRICT'
    #[Groups(['read:Depannage', 'read:Voyage', 'read:Personnel', 'write:Personnel', 'read:Trajet'])]
    private ?Typepersonnel $typepersonnel = null;

    #[ORM\Column(nullable: true)]
    private ?int $identreprise = null;

    /**
     * @var Collection<int, Detailpersonnel>
     */
    #[ORM\OneToMany(targetEntity: Detailpersonnel::class, mappedBy: 'personnel')]
    #[Groups(['read:Personnel'])]
    private Collection $detailpersonnels;

    #[ORM\ManyToOne]
    #[Groups(['read:Personnel', 'write:Personnel'])]
    private ?MediaObject $image = null;

    public function __construct()
    {
        $this->detailpersonnels = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): static
    {
        $this->nom = $nom;

        return $this;
    }

    public function getPrenom(): ?string
    {
        return $this->prenom;
    }

    public function setPrenom(string $prenom): static
    {
        $this->prenom = $prenom;

        return $this;
    }

    public function getContact(): ?string
    {
        return $this->contact;
    }

    public function setContact(string $contact): static
    {
        $this->contact = $contact;

        return $this;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(string $code): static
    {
        $this->code = $code;

        return $this;
    }

    public function getTypepersonnel(): ?Typepersonnel
    {
        return $this->typepersonnel;
    }

    public function setTypepersonnel(?Typepersonnel $typepersonnel): static
    {
        $this->typepersonnel = $typepersonnel;

        return $this;
    }

    public function getIdentreprise(): ?int
    {
        return $this->identreprise;
    }

    public function setIdentreprise(?int $identreprise): static
    {
        $this->identreprise = $identreprise;

        return $this;
    }

    /**
     * @return Collection<int, Detailpersonnel>
     */
    public function getDetailpersonnels(): Collection
    {
        return $this->detailpersonnels;
    }

    public function addDetailpersonnel(Detailpersonnel $detailpersonnel): static
    {
        if (!$this->detailpersonnels->contains($detailpersonnel)) {
            $this->detailpersonnels->add($detailpersonnel);
            $detailpersonnel->setPersonnel($this);
        }

        return $this;
    }

    public function removeDetailpersonnel(Detailpersonnel $detailpersonnel): static
    {
        if ($this->detailpersonnels->removeElement($detailpersonnel)) {
            // set the owning side to null (unless already changed)
            if ($detailpersonnel->getPersonnel() === $this) {
                $detailpersonnel->setPersonnel(null);
            }
        }

        return $this;
    }

    /* -- On vas s'en passer pour ne pas empêcher la suppression du personnel dans ce cas on ne doit pas avoir de cascade
        public function getSoftDeleteBlockers(): array
        {
            $errors = [];
            $detailpersonnels = $this->detailpersonnels;
            if(!$detailpersonnels->isEmpty()) {
                $errors[] = sprintf(
                    'Le personnel est liée à %d dépannage(s) ou voyage(s) actif(s)',
                    $detailpersonnels->count()
                );
            }
            return $errors;
        }
    */

    public function getImage(): ?MediaObject
    {
        return $this->image;
    }

    public function setImage(?MediaObject $image): static
    {
        $this->image = $image;

        return $this;
    }
}
