import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-new',
  templateUrl: './new.component.html',
  styleUrls: ['./new.component.css']
})
export class NewComponent implements OnInit {
  dosage;
  isView=false;
  list;
  productslist=[];
  detailslist=[];
  batchlist=[];
  selectedResult=[];
  products=[];

  constructor(private service:DataAccessService) { }

  ngOnInit(): void {
    this.getDosage();
  }
  getDosage(){
    this.service.get('qc/validation/process.php?type=getDosages').subscribe(response=>{
      this.dosage=response;
    });

  }
  getProducts(index){
    index = index-1;
    if(index !== -1){
      this.list=this.dosage[index];
      this.productslist=this.list['products'];
    }
  }
  getDetails(index){
    index = index-1;
    if(index !== -1){
      this.detailslist = this.productslist[index];
      this.batchlist=this.detailslist['batches'];
      this.isView=true; 
    }  
  } 
  save(data){
    let temp=data.value;
    temp['generic_name']=this.detailslist['generic_name'];
    temp['grade']= this.detailslist['grade'];
    temp['product_code']=this.detailslist['product_code'];
    temp['manufactured_for']=this.detailslist['manufactured_for'];
    if(data.valid)
    this.service.post('qc/validation/process.php?type=saveProcess',JSON.stringify(temp)).subscribe(response=>{
      alertify.success("saved succesfully");
      this.detailslist=[];
      data.reset();
    });
    else{
      alertify.error("not valid")
    }
  }
}


