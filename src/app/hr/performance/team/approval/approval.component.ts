import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-approval',
  templateUrl: './approval.component.html',
  styleUrls: ['./approval.component.css']
})
export class ApprovalComponent implements OnInit {
  isView=false;
  results;
  selectedCheckList=[];

  constructor(private service:DataAccessService) { }
  
  ngOnInit(): void {
    this. getList();
    this. getPending();
  }
 
  getList(){
    this.service.get('hr/appraisalchecklist.php?type=gate_new_team').subscribe(response=>{
      this.results=response;
      console.log('result',this.results);
    })

  }
  getPending(){
    this.service.get('hr/appraisalchecklist.php?type=gate_pending').subscribe(response=>{
      this.results=response;
      console.log('resaweult',this.results);
    })

  }
  view(index) {
    this.selectedCheckList = this.results[index];
    this.isView = true;
    console.log('selectchecklist',this.selectedCheckList);
  }
  update(status) {
        

    this.service.post('hr/appraisalchecklist.php?type=update_status&status=' + status + '&id=' + this.selectedCheckList['department'], JSON.stringify(status)).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('Record updated successfully');
        this.isView = false;
        this.getPending();
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });

  }


}
