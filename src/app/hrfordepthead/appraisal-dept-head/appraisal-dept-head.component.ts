import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-appraisal-dept-head',
  templateUrl: './appraisal-dept-head.component.html',
  styleUrls: ['./appraisal-dept-head.component.css']
})
export class AppraisalDeptHeadComponent implements OnInit {

  
  isView = false;
   results;
   department;
    emp_id: string;
    isDIGI=false
    Status: any;
     constructor(private service:DataAccessService) { }
  
    
    ngOnInit(): void {
      this.department = localStorage.getItem('department');
      this.getapprisals_log()
     }
  
  
    getapprisals_log() {
        this.service.get('hr/appraisalchecklist.php?type=getapprisals_log_for_deptHead&department1='+localStorage.getItem('department')).subscribe((response: any) => {
        this.results = response;
      
      });
    }
 
    

    
    selectedResult=[];
 
    view(index)
    {
      this.selectedResult=this.results[index];
  
      this.isView =  true ;
  
    }
    updateData(status){
      
      this.service.get('hr/appraisalchecklist.php?type=update_dept_status&rise='+this.selectedResult['rise'] +'&status='+status+'&id='+this.selectedResult['id']).subscribe(response => {
        if (response['status'] == 'success') {
          alert('Saved Successfully');
          this.getapprisals_log()
          this.isView = false;
        
          } else {
          alert('Failed: An error occured, please try again!');
        }
      });
    }


    openDigiSign(status){
      this.emp_id = localStorage.getItem('emp_id');
      this.isDIGI = true;
      this.Status=status
    }
  
    loginPassward ='';
    digiSign(data){
  
      if (!data.valid) {
        alert('Passward OR Login PIN Required!!!!');
        return;
      }
   
      this.service.get('login.php?type=checkDigiSIgn&mpin=' + this.loginPassward +'&emp_id=' + this.emp_id).subscribe(response => {
        if (response['status'] == 'success') {
          alertify.success('Digi-Sign Verified successfully');
          this.isDIGI = false;
          this.loginPassward ='';
          this.updateData(this.Status)
  
          
        
          
        }
        else
        {
          alertify.error('Digi-Sign Not Verified');
  
        }
      });
    }
    updateIndend(Status: any, Id: any) {
        throw new Error('Method not implemented.');
    }
    Id(Status: any, Id: any) {
        throw new Error('Method not implemented.');
    }
    
  

  
    
  }
  