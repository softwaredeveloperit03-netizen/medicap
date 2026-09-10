import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-checking',
  templateUrl: './checking.component.html',
  styleUrls: ['./checking.component.css']
})
export class CheckingComponent implements OnInit {

  isView = false;
  results;
  selectedResult: [];
  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getPendingIndends();
  }

  getPendingIndends() {
    this.service.get('purchase/indend/general.php?type=getPendingIndends').subscribe(response => {
      this.results = response;
    });
  }

  updateIndend(status,id){
    this.service.get('purchase/indend/general.php?type=updateIndend&status=' + status + '&id=' + id).subscribe(response => {
      if (response['status']) {
        alertify.success('indend Updated Successfully');
        this.getPendingIndends();
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  }

}
