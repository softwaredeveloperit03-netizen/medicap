import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify: any;

@Component({
  selector: 'app-add-scrap-material',
  templateUrl: './add-scrap-material.component.html',
  styleUrls: ['./add-scrap-material.component.css']
})
export class AddScrapMaterialComponent implements OnInit {
  productList=[];
  grades;
  gst;
  gsts;
  grade='';
scrap_name: any;
scrap_source: any;
  constructor(private service:DataAccessService,private router:Router) { }

  ngOnInit(): void {
    this.service.observableGrade.subscribe(response => {
      this.grades = response;
    });
    // this.service.observableGst.subscribe(response => {
    //   this.gst = response;
    // });
    this.getGST();

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
  getGST(){
    this.service.get('common.php?type=getGST').subscribe(response=>{
      this.gsts=response;
    });
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
}
 