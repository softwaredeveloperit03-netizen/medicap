import { Component, OnInit } from '@angular/core';
import { ActivatedRoute, Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-edit',
  templateUrl: './edit.component.html',
  styleUrls: ['../po-shell.css', './edit.component.css'],
})
export class EditComponent implements OnInit {

  isView = false;
  result:any ;

  products;
  file: File;
  units;
  terms='';

  isProduct = false;
  selectedProduct = [];
  rate = 0;
  qty = 0;

  currancy_rate = 70;
  currency = "INR";

  amount_inr = 0;
  amount_usd = 0;

  dosages;

  product = [];
  isEdit = false;
  constructor(private service: DataAccessService, private _Activatedroute: ActivatedRoute, private router: Router) { }

  ngOnInit() {
    this._Activatedroute.paramMap.subscribe(params => {
      this.getPODetails(params.get('id'));

      this.getDosages();
      this.getUnits();
    });
  }

  getPODetails(id) {
    this.service.get('marketing/po.php?type=getPODetails&id=' + id).subscribe((response: any) => {
      this.result = response;
      console.log('in'+this.result);
      this.isView = true;
    });
    console.log('out'+this.result);

  }

  getDosages() {
    this.service.get('common.php?type=getDosages').subscribe(response => {
      this.dosages = response;
    });
  }

  getProducts(value) {
    this.service.get('common.php?type=getProductsByDosage&dosage_form=' + value).subscribe(response => {
      this.products = response;
    });
  }

  getUnits(){
    this.service.get('marketing/po.php?type=getUnits').subscribe(response => {
      this.units = response;
    });
  }

  onFileChange($event) {
    this.file = $event.target.files[0];
  }

  addProduct(data) {
    if (!data.valid) {
      alert('All fields are required');
      return;
    }
    let temp = data.value;
    temp['product_name'] = this.selectedProduct["product_name"];
    temp['grade'] = this.selectedProduct["grade"];
    temp['generic_name'] = this.selectedProduct["generic_name"];

    let products = this.result['products'];
    products[products.length] = data.value;
    this.result['products'] = products;
    data.resetForm();
    this.isProduct = false;
  }

  selectProduct(index) {
    index = index - 1;
    if (index !== -1) {
      this.selectedProduct = this.products[index];
    }
  }

  deleteProduct(index) {
    let products = this.result['products'];
    products.splice(index, 1);
    this.result['products'] = products;
  }
  
  addPayment(data){
    if (!data.valid) {
      alert('All fields are requierd');
      return;
    }
    let terms = this.result['terms'];
    terms[terms.length] = data.value;
    this.result['terms'] = terms;
    data.resetForm();
  }

  deletepayment(index) {
    let terms = this.result['terms'];
    terms.splice(index, 1);
    this.result['terms'] = terms;
  }

  saveForm(data) {
    if (!data.valid) {
      alert('All fields are required');
      return;
    }

    this.service.post('marketing/po.php?type=editPO', this.result).subscribe(response => {
      const result = JSON.parse(JSON.stringify(response));
      if (result.status === 'success') {
        data.resetForm();
        alert('Saved Successfully');
        this.router.navigate(['/po']);
      } else {
        alert('An error has occurred, please try again');
      }
    });
  }

  calculation() {
    if (this.currency == "INR") {
      this.amount_inr = this.rate * this.qty;
      this.amount_usd = +parseFloat(((this.rate * this.qty) / this.currancy_rate) + "").toFixed(2);
    } else {
      this.amount_usd = this.rate * this.qty;
      this.amount_inr = +parseFloat((this.rate * this.qty * this.currancy_rate) + "").toFixed(2);
    }
  }

  editProduct(index) {
    let products = this.result['products'];
    this.product = products[index];
    this.isEdit = true;
  }

  updateProduct(data) {
    
  }

}
