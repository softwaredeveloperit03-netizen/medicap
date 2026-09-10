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

  ngOnInit() {
    this.getPendingUnitFormulas();
  }

  getPendingUnitFormulas(){
    this.service.get('production/unitformula.php?type=getPendingUnitFormulas').subscribe(response => {
      this.results = response;
    });
  }

  view(index){
    this.selectedResult = this.results[index];
    this.isView = true;
  }

  approveUnitFormula(status) {
    this.service.get('production/unitformula.php?type=approveUnitFormula&status=' + status + '&id=' + this.selectedResult['id']).subscribe(response => {
      if(response['status'] == 'success'){
        alert('Data Updated Successfully!');
        this.isView = false;
        this.getPendingUnitFormulas();
      }else{
        alert('An Error Occured, Please try again!');
      }
    });
  }

}
