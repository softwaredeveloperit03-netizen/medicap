import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-request',
  templateUrl: './request.component.html',
  styleUrls: ['./request.component.css'],
})
export class RequestComponent implements OnInit {

  results;
 constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getTechnicalLog();
  }

  getTechnicalLog() {
    this.service.get('production/technical.php?type=getPendingRequests').subscribe(response=>{
      this.results = response;
    });
  }

  update(status, id) {
    this.service.get('production/technical.php?type=acceptRequest&id=' + id + '&status=' + status).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success(response['msg']);
        this.getTechnicalLog();
      } else {
        alertify.error(response['msg']);
      }
    });
  }

}
