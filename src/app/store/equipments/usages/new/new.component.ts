import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-new',
  templateUrl: './new.component.html',
  styleUrls: ['./new.component.css']
})
export class NewComponent implements OnInit {
  equipments;
  operators;
  selectedResult=[];
  selectedData=[];
  constructor(private service:DataAccessService,private router:Router) { }

  ngOnInit(): void {
    this.getEquipments();
    this.getOperator();
  }
  getEquipments(){
    this.service.get('equipments.php?type=getEquipments').subscribe(response=>{
      this.equipments=response;
    })
  }
  getOperator(){
    this.service.get('common.php?type=getOperators').subscribe(response=>{
      this.operators=response;
    })
  }
  getCode(index){
    index=index-1;
    if(index !==-1){
      this.selectedResult=this.equipments[index];
    }
    
  }
  getClean(index){
    index=index-1;
    if (index !== -1) {
      let equipments = this.selectedResult['equipments'];
      this.selectedData = equipments[index];
    }
  }
  saveUsage(data){
    if(!data.valid){
      alertify.error('all fields are required');
      return;
    }

    if (this.selectedData['clean'] == 'no') {
      alertify.error('Equipment not yet cleaned');
      return;
    }

    this.service.post('equipments.php?type=saveGeneralEquipmentUsages',JSON.stringify(data.value)).subscribe(response=>{
      if(response['status']=='success'){
        alertify.success('Equipment usage save successfuly');
        data.resetForm();
        this.router.navigate(['/store/equipments/usages']);
      }else('some error occured!');
    });
  }
}
