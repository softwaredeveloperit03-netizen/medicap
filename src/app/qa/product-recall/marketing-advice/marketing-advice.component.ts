import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { FormGroup, FormControl, Validators, FormBuilder } from '@angular/forms';

@Component({
  selector: 'app-marketing-advice',
  templateUrl: './marketing-advice.component.html',
  styleUrls: ['./marketing-advice.component.css']
})
export class MarketingAdviceComponent implements OnInit {

  isView= false;
  isNew = false;

  

  selectedEntry;
  advice;
  products;

  reactiveForm: FormGroup;
  constructor(private service: DataAccessService, private fb: FormBuilder) {
    this.reactiveForm = this.fb.group({
      date: ['', [ Validators.required ]],
      name: ['', [ Validators.required ]],
      address: ['', [ Validators.required ]],
      batch_no: ['', [ Validators.required ]],
      product_name: ['', [ Validators.required ]]
    });
  }

  ngOnInit() {
    this.getAdvice();
    this.getProducts();
  }

  getAdvice() {
    this.service.get('qaDepartment.php?type=getAdvice').subscribe(response => {
      this.advice = response;
    });
  }

  getProducts() {
    this.service.get('qaDepartment.php?type=getApprovedProducts').subscribe(response => {
      this.products = response;
    });
  }

  viewPDF(index) {
    this.selectedEntry = this.advice[index];
    this.service.open('pdf1/product_recall.php?type=generateAdvicePDF&id=' + this.selectedEntry.id);
  }


  saveAdvice () {
    if (this.reactiveForm.valid) {
      alert('All fields are required');
      return;
    }
    const formData = new FormData(); 
    formData.append('name', this.reactiveForm.value.name);
    formData.append('date', this.reactiveForm.value.date);
    formData.append('address', this.reactiveForm.value.address);
    formData.append('batch_no', this.reactiveForm.value.batch_no);
    formData.append('product_name', this.reactiveForm.value.product_name);

    this.service.post('qaDepartment.php?type=saveAdvice', formData).subscribe(response => {
      if (response['status'] === 'success') {
        this.isNew = false;
        this.reactiveForm.reset();
        this.getAdvice();
      } else {
        alert('error');
      }
    });
  }


  viewAdvice(index) {
    this.isView = true;
    this.selectedEntry = this.advice[index];
  }
}
