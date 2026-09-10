import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { Router } from '@angular/router';
declare let alertify;
@Component({
  selector: 'app-procedure',
  templateUrl: './procedure.component.html',
  styleUrls: ['./procedure.component.css']
})
export class ProcedureComponent implements OnInit {
  procedureList:any=[];
  constructor(private service: DataAccessService, private router: Router) { }

  ngOnInit(): void {
     this.getChecklist();
  }
   getChecklist(){
  this.service.get('engineering/qualification.php?type=getEngineeringProcedure')
  .subscribe(response => { 
    this.procedureList=response;


    });
}
  addData(data) {
   let temp=data.value;
    
    this.service.post('engineering/qualification.php?type=saveEngineeringProcedure',JSON.stringify(temp))
    .subscribe(response => {
      if(response['status'] === 'success') {
        alertify.success('success');
        data.resetForm();
        this.getChecklist()
        
        
      } else {
        alertify.error(response['status']);
      }
      });
  }

  delData(id) {
     this.service.post('engineering/qualification.php?type=deleteData&id='+id+'&Table=EngineeringProcedure',null)
    .subscribe(response => {
      if(response['status'] === 'success') {
        alertify.success('success');
       
        this.getChecklist()
        
        
      } else {
        alertify.error(response['status']);
      }
      });
  }
  download(){
    this.service.open('');
      
   }
   
}
