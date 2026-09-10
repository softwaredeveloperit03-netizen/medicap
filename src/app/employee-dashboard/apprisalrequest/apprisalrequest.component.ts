import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-apprisalrequest',
  templateUrl: './apprisalrequest.component.html',
  styleUrls: ['./apprisalrequest.component.css']
})
export class ApprisalrequestComponent implements OnInit {

  isView = false;
  isNew = false;
  results;
  selectedCheckList ;
  selectedChec ;
    constructor(private service:DataAccessService) { }
  
    
    ngOnInit(): void {
      this.getapprisals_log()
      this.getDesignation()
    }
  
    // view(index){
    //   this.orders=selected
    // }
  
    getapprisals_log() {
        this.service.get('hr/appraisalchecklist.php?type=getapprisals_log').subscribe((response: any) => {
        this.results = response;
      
      });
    }
    designations;
    getDesignation() {
        this.service.get('hr/appraisalchecklist.php?type=getDesignation').subscribe((response: any) => {
        this.designations = response;
      
      });
    }
  
    view(index)
    {
      this.isView =  true ;
      this.selectedChec = this.results[index];
  
    }


    save(data){
      if(!data.valid){
        alertify.error('All feilds are required');
        return;
      }
      let temp = data.value;
      this.service.post('hr/appraisalchecklist.php?type=save_apprisal', JSON.stringify(temp)).subscribe(response => {
        if (response['status'] == 'success') {
          alert('Saved Successfully');
          this.getapprisals_log()
          this.isNew = false;
          data.reset();
          
          } else {
          alert('Failed: An error occured, please try again!');
        }
      });
    }
    
  }
  