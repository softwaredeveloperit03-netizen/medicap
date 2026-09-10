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
  clean_by='';
  constructor( private service:DataAccessService,private router:Router) { }

  ngOnInit(): void {
    this.getEquipments();
    this.getLabours();
  }
  getEquipments(){
    this.service.get('equipments.php?type=getEquipments').subscribe(response=>{
      this.equipments=response;
    })
  }

  getLabours(){
    this.service.get('common.php?type=getLabours').subscribe(response=>{
      this.operators=response;
    })
  }
  getCode(index){
    index=index-1;
    if(index !==-1){
      this.selectedResult=this.equipments[index];
    }
    
  }

  saveCleaning(data){
    if(!data.valid){
      alertify.error('all feilds are required');
    }
    this.service.post('equipments.php?type=saveGeneralEquipmentCleaning',JSON.stringify(data.value)).subscribe(response=>{
      if(response['status']=='success'){
        data.resetForm();
        this.router.navigate(['/logbooks/equipments/cleaning']);
        alertify.success('Equipment Cleaning  successfuly');
      }else('some error occured!');
    });
  }
}