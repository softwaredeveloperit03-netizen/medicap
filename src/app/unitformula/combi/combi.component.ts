import { Component, OnInit } from '@angular/core';
import { FormBuilder } from '@angular/forms';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-combi',
  templateUrl: './combi.component.html',
  styleUrls: ['./combi.component.css']
})
export class CombiComponent implements OnInit {

    constructor(private service: DataAccessService, private router: Router) { }
  ngOnInit(): void {
    this.getMaterialType();
    this.Getcombis();
  }
category
dosage_form
combis;
 
  Getcombis(){
    this.service.get('master/materialtype.php?type=Getcombis').subscribe(response => {
      this.combis= response;
     });
  }
    types;
  getMaterialType(){
    this.service.get('master/materialtype.php?type=getMatTypeByMatType_UOM').subscribe(response => {
      this.types= response;
     });
  }
dosage_type
product_type
    products: any;

  getProductByDosageTypeForm() {
    const params = `master/product.php?type=getProductByDosageTypeForm_uom`
      + `&category=${encodeURIComponent(this.category)}`
      + `&dosage_form=${encodeURIComponent(this.dosage_form)}`
      + `&dosage_type=${encodeURIComponent(this.dosage_type)}`
      + `&product_type=${encodeURIComponent(this.product_type)}`;

    this.service.get(params).subscribe((response) => {
      this.products = response;
    });
  }


  selectedCombi=[];
  selectedproducts=[];
  getCombidata(index){
    this.selectedCombi=this.combis[index-1];
  }
  getProductdata(index){
    this.selectedproducts=this.products[index-1];
  }
  combiProductList=[]
  AddSubProds(data){
      let temp =data.value;
      temp['product_code']=this.selectedproducts['product_code']
      this.combiProductList[this.combiProductList.length]=temp;
      data.resetForm();
      console.log(temp);
  }

  Save(){
    let temp={}
    temp['combi_product_name']=this.selectedCombi['product_name']
    temp['combi_product_code']=this.selectedCombi['product_code']
    temp['combiProductList']=this.combiProductList;

     this.service.post('production/unitformula.php?type=saveCombiMaster', JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success(' initiated successfully!');
        this.combiProductList=[];
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  }
}
