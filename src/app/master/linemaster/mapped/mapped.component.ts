import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-mapped',
  templateUrl: './mapped.component.html',
  styleUrls: ['./mapped.component.css']
})
export class MappedComponent implements OnInit {

  constructor(private service: DataAccessService) { }

  ngOnInit(): void {
    this.getlog();
  }
  results
  getlog(){
    this.service.get('bmr/process.php?type=stageLinemasterLog').subscribe(response => {
      this.results = response;
    })
  }
  isequip=false;
  equipmentList=[];
  AddEqup(index){
    this.equipmentList=[];
   
    this.isequip=true;
    this.equipmentList=this.results[index]['equipmentList'];
  }
  isStageViewModal=false;
  openStageViewModal(index){
    this.selectedStages=this.results[index]['stages'];
    this.isStageViewModal=true;
  }
     selectedProduct: any = {};
     product_name: string = '';
  onChangeProduct(index){
    this.selectedProduct = this.product_list[index-1] || {};
    this.product_name = this.selectedProduct.product_name || '';
    console.log('this.selectedProduct :>> ', this.selectedProduct);
  }
  selectedStages=[];
  currentStageViewIndex: number = -1;
selectedIndex=-1;
mappedProduct=[]
isMapped=false;
selectedResult
product_list=[]
    MapProduct(index){
    this.selectedIndex=index;
    this.mappedProduct=[];
   
    this.isMapped=true;
    this.selectedResult=this.results[index];
    this.mappedProduct=this.results[index]['mappedProduct'];
    this.product_list=this.results[index]['product_list'];
  }

  addProduct(termForm: any) {
    // Check if a product is selected
    if (!this.selectedProduct || !this.selectedProduct.product_code) {
      alertify.warning('Please select a product first');
      return;
    }

    // Check if product is already mapped
    const isAlreadyMapped = this.mappedProduct.some(
      (product: any) => product.product_code === this.selectedProduct.product_code
    );

    if (isAlreadyMapped) {
      alertify.warning('This product is already mapped');
      return;
    }

    // Add product to mapped list
    this.mappedProduct.push({
      product_code: this.selectedProduct.product_code,
      product_name: this.selectedProduct.product_name || this.product_name
    });

    // Clear selection
    this.selectedProduct = {};
    this.product_name = '';

    alertify.success('Product added to mapping list');
  }

  removeProduct(index: number) {
    if (confirm('Are you sure you want to remove this product from mapping?')) {
      this.mappedProduct.splice(index, 1);
      alertify.success('Product removed from mapping');
    }
  }

   saveMapped(){
    let temp={};
    temp['mappedProduct']=this.mappedProduct;
    temp['id']=this.selectedResult['id'];
    console.log('temp :>> ', temp);
      this.service.post('bmr/process.php?type=updateMapedProduct', JSON.stringify(temp)).subscribe(response => {
          if (response['status'] === 'success') {
            alertify.success("Saved Successfully");
            this.mappedProduct=[];
          }else{
            alertify.error('Some Error Occured!');
          }
        });

    } 

}