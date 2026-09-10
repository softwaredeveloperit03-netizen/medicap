import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-new',
  templateUrl: './new.component.html',
  styleUrls: ['./new.component.css']
})
export class NewComponent implements OnInit {
   constructor(private service: DataAccessService) { }

  ngOnInit() {

    this.getClients(); 
    this.getClient(); 
    this.getProducts(); 
    // this.getPackSize(); 
 
  }

  pack_size;
  product_code = '';
  billing_type = '';
   

  clients;
  getClients() {
    this.service.get('common.php?type=getClients').subscribe(response => {
      this.clients = response;
    });
  }
   clients1;
  getClient() {
    this.service.get('marketing/client.php?type=getClientWithGroup').subscribe((response) => {
        this.clients1 = response;
      });
  }
    selectedClient1 = [];
    subGroupSeris = [];
    mainGroupclient_code = [];
    mainGroupName:any;
  getSubGroup(index){
    this.selectedClient1=this.clients1[index-1]
    this.mainGroupName=this.clients1[index-1]['LglNm']
    this.mainGroupclient_code=this.clients1[index-1]['client_code']
    this.subGroupSeris = this.clients1[index-1]?.clientGroups;
  }
 
  products;
  getProducts() {
    this.service.get('master/product.php?type=getBrandProductsLog').subscribe((response) => {
        this.products = response;
     });
  } 
 
  // getPackSize() {
  //   this.service.get('common.php?type=getPackSizesmar').subscribe(response => {
  //     this.pack_size = response;
  //   });
  // }
   
  packingStyle = 0;
  packingUnit = '';
  getPackingStyleUnit(index){
    this.packingUnit = '';
    const unit  = this.pack_size[index-1]?.pack_sizes;
    this.packingStyle   = parseInt(this.pack_size[index-1]?.pack_sizes);
    this.packingUnit = unit.match(/[A-Za-z]+/g)?.[0] || '';
    console.log("packingUnit" + this.packingUnit);
  }

  product_name = '';
  category='';
  getProductName(index){
    this.product_name = '';
    this.product_name = this.products[index-1]?.product_name;
    this.pack_size = this.products[index-1]?.pack_sizes;
    this.category=this.products[index-1]?.category
  }
  
   
  file: File;
  onFileChange($event) {
    this.file = $event.target.files[0];
  }
 
 
  productList = [];
  addProduct(data) {
    if(!data.valid){
      alertify.error("All Field Required!!!!!!!!");
      return;
    }
 
    let temp = data.value;
    temp['packingStyle'] = this.packingStyle;
    temp['product_name'] = this.product_name;
    temp['packingUnit'] = this.packingUnit;
    temp['category'] = this.category;
    this.productList.push(data.value);
    data.reset()
  }
   
  deleteProduct(index) {
    this.productList.splice(index, 1);
  }
 
 
  conisgnee ='';
  client_code ='';
  po_type ='';
  
  saveForm(data) {
console.log('data :>> ', data);
     if (!data.valid) {
      alert('All fields are required');
      return;
    }
     if (this.billing_type=='PO for Billing' && this.client_code=='') {
      alert('Client Group required');
      return;
    }
     if (this.billing_type=='PO for Billing' && this.conisgnee=='') {
      alert('Consignee Name required');
      return;
    }

 
    const formData = new FormData();

    let temp = data.value;
    for (let key in temp) {
      let value = temp[key];
      formData.append(key, value);
    }
 
    formData.append('products', JSON.stringify(this.productList));
    formData.append('mainGroupName', this.mainGroupName);
 
     
    if (this.file !== undefined && this.file !== null) {
      formData.append('fileUp', this.file, this.file.name);
    }
      
    this.service.post('marketing/po.php?type=receivePO', formData).subscribe(response => {
      const result = JSON.parse(JSON.stringify(response));
      if (result.status === 'success') {
        data.resetForm();
        this.productList = [];
        alert('Saved Successfully');
      } else {
        alert('An error has occurred, please try again');
      }
    });
  }
 
 
  
 


}
