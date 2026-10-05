@extends('frontend.layouts.master')
@section('meta_title', $page->translation->seo_title ?? $page->translation->name)
@section('meta_description', $page->translation->seo_description ?? $page->translation->name)

