import { Component, OnInit } from '@angular/core';
import { FormBuilder, FormGroup, Validators } from '@angular/forms';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-new',
  templateUrl: './new.component.html',
  styleUrls: ['./new.component.css']
})
export class NewComponent implements OnInit {

  clients = [];
  Products = [];
  selectedClient=[];
  productList = [];
  selectedProduct;

  clientForm: FormGroup;
  reactiveForm: FormGroup;

  transporters;
  constructor(private service: DataAccessService, private fb: FormBuilder,private router:Router) { }

  ngOnInit() {
    this.getClients();
    this.getProducts();
    this.getTransporters();
  }

  getClients() {
    this.service.get('dispatch.php?type=getClients').subscribe(response=> {
      this.clients = JSON.parse(JSON.stringify(response));
    });
  }

  getTransporters() {
    this.service.get('marketing/workorder.php?type=getTransporters').subscribe(response => {
      this.transporters = response;
    });
  }

  getClientList(index){
    index=index-1;
    this.selectedClient=this.clients[index];
  }
  
  getProducts() {
    this.service.get('common.php?type=getProducts').subscribe((response: any) => {
      this.Products = response;
    });
  }

  getProductDetails(index) {
    this.selectedProduct = this.Products[index];
  }

  addProduct(data) {
    if (!data.valid) {
      alert('All fields are required');
      return;
    }
    this.productList[this.productList.length] = data.value;
  }

  onProductDelete(index) {
    this.productList.splice(index, 1);
  }

  onSubmit(data) {
    if(!data.valid){
      alert("All Fields are required");
      return;
    }
   let temp=data.value;
    temp['clients']=this.selectedClient;
    temp['products']=this.productList;
    this.service.post('marketing/workorder.php?type=saveWorkOrder',JSON.stringify(temp)).subscribe(response => {
      if (response['status']=="success") {
        data.resetForm();
        alert("Recored inserted Succesfully")
        this.router.navigate(['/workorder']);
      } else {
        alert("Record is not inserted");
      }
    });
  }

}
