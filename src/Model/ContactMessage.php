<?php

namespace App\Model;

class ContactMessage
{
    private string $name;

    private string $email;

    private string $contact;

    /**
     * @param string $name
     * @param string $email
     * @param string $contact
     */
    public function __construct(string $name, string $email, string $contact)
    {
        $this->name = $name;
        $this->email = $email;
        $this->contact = $contact;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return ContactMessage
     */
    public function setName(string $name): ContactMessage
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return string
     */
    public function getEmail(): string
    {
        return $this->email;
    }

    /**
     * @param string $email
     * @return ContactMessage
     */
    public function setEmail(string $email): ContactMessage
    {
        $this->email = $email;
        return $this;
    }

    /**
     * @return string
     */
    public function getContact(): string
    {
        return $this->contact;
    }

    /**
     * @param string $contact
     * @return ContactMessage
     */
    public function setContact(string $contact): ContactMessage
    {
        $this->contact = $contact;
        return $this;
    }
}