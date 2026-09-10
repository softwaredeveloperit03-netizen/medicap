import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-approval',
  templateUrl: './approval.component.html',
  styleUrls: ['./approval.component.css']
})
export class ApprovalComponent implements OnInit {

  results;

  constructor(private service: DataAccessService) { }

  ngOnInit(): void {
    this.getPendingTemperatures();
  }

  getPendingTemperatures(){
    this.service.get('qa/temperature.php?type=getPendingTemperatures').subscribe(response => {
      this.results = response;
    });
  }

  updateMaterial(status, id) {
    this.service.get('qa/temperature.php?type=updateTemperature&status=' + status + '&id=' + id).subscribe(response => {
      if (response['status'] == 'success') {
        alert('Record Updated successfully');
        this.getPendingTemperatures();
      } else {
        alert('Failed: An error occured, please try again!');
      }
    });
  }

}
