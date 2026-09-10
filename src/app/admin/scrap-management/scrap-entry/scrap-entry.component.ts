import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify: any;

@Component({
  selector: 'app-scrap-entry',
  templateUrl: './scrap-entry.component.html',
  styleUrls: ['./scrap-entry.component.css']
})
export class ScrapEntryComponent implements OnInit {
  productList=[];
  grades;
  gst;
  grade='';
    clients;
  constructor(private service:DataAccessService,private router:Router) { }

  ngOnInit(): void {
    this.service.observableGrade.subscribe(response => {
      this.grades = response;
    });
    this.service.observableGst.subscribe(response => {
      this.gst = response;
    });
    this.getMaterials();
  }


  adddata(data){
    if(!data.valid){
      alertify.error("Add Atleast One Make");
      return;
    }
    let temp=data.value;
    this.productList[this.productList.length] = temp;
    data.reset();
  }
  delData(index){
    this.productList.splice(index,1);
  }
  getBatches(event) {
    if (event.target.value < 0) {
      event.target.value = 0;
    }
  }
  getBranch(event) {
    if (event.target.value < 0) {
      event.target.value = 0;
    }
  }
  

  save(data){
    if(!data.valid){
      alertify.error('All Field are required');
      return;
    }
    let temp=data.value;
    temp['make']=this.productList;
    this.service.post('qc/chemical.php?type=saveChemical',JSON.stringify(temp)).subscribe(response =>{
      if(response['status']=='success') {
        alertify.success("Record Inserted Succesfully");
        data.resetForm();
        this.router.navigate(['/master/chemical']);
      } else {
        alertify.error("Failed:dupilcate entry for chemical name");
      }
    });
  
  }
  getMaterials(){
    this.service.get('marketing/client.php?type=getmaterial').subscribe(response => {
      this.clients = response;
      console.log("rd",this.clients);
    });
  }


}

 
