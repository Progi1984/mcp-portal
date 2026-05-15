<?php

namespace App\Form;

use App\Entity\McpServer;
use App\Enum\McpServerType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Positive;
use Symfony\Component\Validator\Constraints\Url;

class McpServerFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('name', TextType::class, [
            'label' => 'form.mcp.name',
            'attr'  => ['class' => 'form-control', 'placeholder' => 'form.mcp.name_placeholder'],
            'constraints' => [new NotBlank(message: 'validation.name_required')],
        ]);

        match ($options['server_type']) {
            McpServerType::Castopod            => $this->addCastopodFields($builder),
            McpServerType::GoogleSearchConsole => $this->addGscFields($builder),
            McpServerType::Matomo              => $this->addMatomoFields($builder),
        };
    }

    private function addMatomoFields(FormBuilderInterface $builder): void
    {
        $builder
            ->add('matomoUrl', UrlType::class, [
                'label'   => 'form.mcp.matomo.url',
                'mapped'  => false,
                'attr'    => ['class' => 'form-control', 'placeholder' => 'https://analytics.example.com'],
                'constraints' => [
                    new NotBlank(message: 'validation.url_required'),
                    new Url(message: 'validation.url_invalid'),
                ],
            ])
            ->add('matomoApiToken', TextType::class, [
                'label'  => 'form.mcp.matomo.api_token',
                'mapped' => false,
                'attr'   => ['class' => 'form-control font-monospace', 'placeholder' => 'abc123…', 'autocomplete' => 'off'],
                'constraints' => [new NotBlank(message: 'validation.api_token_required')],
            ])
            ->add('matomoSiteId', IntegerType::class, [
                'label'  => 'form.mcp.matomo.site_id',
                'mapped' => false,
                'attr'   => ['class' => 'form-control', 'min' => 1],
                'constraints' => [
                    new NotBlank(message: 'validation.site_id_required'),
                    new Positive(message: 'validation.site_id_positive'),
                ],
            ]);
    }

    private function addGscFields(FormBuilderInterface $builder): void
    {
        $builder
            ->add('gscSiteUrl', TextType::class, [
                'label'  => 'form.mcp.gsc.site_url',
                'mapped' => false,
                'attr'   => [
                    'class'       => 'form-control font-monospace',
                    'placeholder' => 'https://example.com/ or sc-domain:example.com',
                ],
                'constraints' => [new NotBlank(message: 'validation.gsc.site_url_required')],
            ])
            ->add('gscServiceAccountJson', TextareaType::class, [
                'label'  => 'form.mcp.gsc.service_account_json',
                'mapped' => false,
                'attr'   => [
                    'class'       => 'form-control font-monospace',
                    'rows'        => 8,
                    'placeholder' => "{\n  \"type\": \"service_account\",\n  \"project_id\": \"…\"\n}",
                    'autocomplete' => 'off',
                ],
                'constraints' => [new NotBlank(message: 'validation.gsc.service_account_required')],
            ]);
    }

    private function addCastopodFields(FormBuilderInterface $builder): void
    {
        $builder
            ->add('castopodUrl', UrlType::class, [
                'label'  => 'form.mcp.castopod.url',
                'mapped' => false,
                'attr'   => ['class' => 'form-control', 'placeholder' => 'https://podcasts.example.com'],
                'constraints' => [
                    new NotBlank(message: 'validation.url_required'),
                    new Url(message: 'validation.url_invalid'),
                ],
            ])
            ->add('castopodUsername', TextType::class, [
                'label'  => 'form.mcp.castopod.username',
                'mapped' => false,
                'attr'   => ['class' => 'form-control', 'autocomplete' => 'off'],
                'constraints' => [new NotBlank(message: 'validation.castopod.username_required')],
            ])
            ->add('castopodPassword', TextType::class, [
                'label'  => 'form.mcp.castopod.password',
                'mapped' => false,
                'attr'   => ['class' => 'form-control font-monospace', 'autocomplete' => 'off'],
                'constraints' => [new NotBlank(message: 'validation.castopod.password_required')],
            ])
            ->add('castopodOp3ApiKey', TextType::class, [
                'label'    => 'form.mcp.castopod.op3_api_key',
                'mapped'   => false,
                'required' => false,
                'attr'     => ['class' => 'form-control font-monospace', 'placeholder' => 'Your op3.dev API key', 'autocomplete' => 'off'],
            ])
            ->add('castopodOp3ShowUuid', TextType::class, [
                'label'    => 'form.mcp.castopod.op3_show_uuid',
                'mapped'   => false,
                'required' => false,
                'attr'     => ['class' => 'form-control font-monospace', 'placeholder' => 'e.g. a18389b8a52d4112a782b32f40f73df6'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class'  => McpServer::class,
            'server_type' => null,
        ]);

        $resolver->setAllowedTypes('server_type', ['null', McpServerType::class]);
    }
}
