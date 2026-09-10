import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-approval',
  templateUrl: './approval.component.html',
  styleUrls: ['./approval.component.css']
})
export class ApprovalComponent implements OnInit {

  isView = false;
  results;

  selectedResult = []; 
  constructor(private service:DataAccessService) { }

  ngOnInit(): void {
    this.getPedingRequirements();
  }

  getPedingRequirements(){
    this.service.get('qa/qualification.php?type=getPedingRequirements').subscribe(response => {
      this.results = response;
    });
  }

  view(index){
    this.selectedResult = this.results[index];
    this.isView = true;
  }

  saveForm(status){
    this.service.get('qa/qualification.php?type=updateRequirement&status=' + status + '&id=' + this.selectedResult['id']).subscribe(response => {
      if(response['status'] == 'success'){
        alert('Data Updated Successfully!');
        this.isView = false;
        this.getPedingRequirements();
      }else{
        alert('Failed an error occured,Please try again!');
      }
    });
  }

}
