import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';


@Component({
  selector: 'app-checking',
  templateUrl: './checking.component.html',
  styleUrls: ['./checking.component.css']
})
export class CheckingComponent implements OnInit {

  result;
  selectedReport=[];
  isView=false;
 
  constructor(private service: DataAccessService ) {  }
  
  ngOnInit(): void {
  
    this.getIssueLog();
  }

  getIssueLog(){
    this.service.get('store/jobwork.php?type=getJobworkForChecking').subscribe(response =>{
      this.result =response;
    });
  }

  view(index){
    this.selectedReport =  this.result[index];
    this.isView = true;
  }


  checkJobwork() {
    
    let temp = {};
    temp['jobwork_no'] = this.selectedReport['jobwork_no'];

      this.service.post('store/jobwork.php?type=checkJobwork', JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        alert('Approved Successfully');
         this.isView = false;
         this.getIssueLog();
      } else {
        alert('Failed: An error occured, please try again!');
      }
    });
  }


 
}