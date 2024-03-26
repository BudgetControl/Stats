<?php
namespace Budgetcontrol\Workspace\Service\Traits;

use stdClass;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Serializer as SymfonySerializer;


trait Serializer {

    private SymfonySerializer $serializer;

    private function build()
    {
        $encoders = [new XmlEncoder(), new JsonEncoder()];
        $normalizers = [new ObjectNormalizer()];
        $this->serializer = new SymfonySerializer($normalizers,$encoders);
    }

    public function toSerialize(): stdClass
    {
        $class = new stdClass();
        foreach($this as $key => $value) {
            if($value instanceof \Symfony\Component\Serializer\Serializer) {
                continue;
            }
            
            if(is_object($value)) {
                $class->$key = $value->toArray();
            } else {
                $class->$key = $value;
            }
        }
        return $class;
    }

    public function toJson(array $ignoredAttributes = []): string
    {
        $this->build();
        return $this->serializer->serialize($this->toSerialize(),'json',[AbstractNormalizer::IGNORED_ATTRIBUTES => $ignoredAttributes]);
    }

    public function toArray(array $ignoredAttributes = []): array
    {
        return (array) json_decode($this->toJson($ignoredAttributes));
    }
}