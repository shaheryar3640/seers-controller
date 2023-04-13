<?php

namespace App\Http\Controllers;

use App\Models\Faq;
use App\Models\FaqProduct;
use Illuminate\Http\Request;

class FaqProductController extends Controller
{
    public function show()
    {
        return redirect()->route('cookie-consent-management');
    }

    public function productFAQ($slug)
    {   
        $product = FaqProduct::where('slug', $slug)->first();
        $name = $product->product_name;
        $productFaqs = $product->faqs;
        return view('showFaqs', compact('productFaqs', 'name'));
    }

    public function californiaConsumerPrivacyAct() {
        $product = FaqProduct::where('slug', 'california-consumer-privacy-act')->first();
        $name = $product->product_name;
        $productFaqs = $product->faqs;
        return view('showFaqs', compact('productFaqs', 'name'));
    }

    public function euRepresentative() {
        $product = FaqProduct::where('slug', 'eu-representative')->first();
        $name = $product->product_name;
        $productFaqs = $product->faqs;
        return view('showFaqs', compact('productFaqs', 'name'));
    }

    public function gdprStaffTrainingKnowledge() {
        $product = FaqProduct::where('slug', 'gdpr-staff-training-knowledge')->first();
        $name = $product->product_name;
        $productFaqs = $product->faqs;
        return view('showFaqs', compact('productFaqs', 'name'));
    }

    public function dataProtectionImpactAssessmentKnowledge() {
        return redirect()->route('dpia');
//        $product = FaqProduct::where('slug', 'data-protection-impact-assessment-knowledge')->first();
//        $name = $product->product_name;
//        $productFaqs = $product->faqs;
//        return view('showFaqs', compact('productFaqs', 'name'));
    }

    public function outsourcedDpoKnowledge() {
        $product = FaqProduct::where('slug', 'outsourced-dpo-knowledge')->first();
        $name = $product->product_name;
        $productFaqs = $product->faqs;
        return view('showFaqs', compact('productFaqs', 'name'));
    }

}
