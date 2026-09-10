import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify: any;
@Component({
  selector: 'app-new',
  templateUrl: './new.component.html',
  styleUrls: ['./new.component.css']
})
export class NewComponent implements OnInit {
  productList=[];
  grades;
  grade='';
  constructor(private service:DataAccessService,private router:Router) { }

  ngOnInit(): void {
    this. getGrades();
  }


  adddata(data){
    if(!data.valid){
      alertify.error("All fiels are required");
      return;
    }
    let temp=data.value;
    this.productList[this.productList.length] = temp;
    data.reset();
  }
  delData(index){
    this.productList.splice(index,1);
  }

  getGrades() {
    this.service.get('common.php?type=getGrades').subscribe(response => {
      this.grades = response;
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
