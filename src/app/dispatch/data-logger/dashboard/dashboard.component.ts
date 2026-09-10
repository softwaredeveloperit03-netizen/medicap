import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.css'],
})
export class DashboardComponent implements OnInit {
  isNew = false;
  finalResults:any;
  results = [
    { data_logger_number: 'DL-001', number_of_channels: 5 },
    { data_logger_number: 'DL-002', number_of_channels: 8 },
    { data_logger_number: 'DL-003', number_of_channels: 3 }
  ];
  constructor(private service: DataAccessService,private router: Router) {}
  ngOnInit(): void 
  {
    this.getDataLogger()
  }


  getDataLogger() {
    this.service.get('dispatch.php?type=getDataLogger').subscribe(response => {
      this.finalResults =  response;
    });
  }

  save(data) {
    console.log('data',data);
    if (!data.valid) {
      alertify.error('All feilds are required');
      return;
    }
    let temp = data.value;
    this.service.post('dispatch.php?type=saveDataLogger',JSON.stringify(temp)).subscribe((response) => {
        if (response['status'] == 'success') {
          alert('Saved Successfully');
          this.router.navigate(['/dispatch/data-logger-master'])
        } else {
          alert('Failed: An error occured, please try again!');
        }
      });
  }
  deleteData(id: any) {
    this.service.get('dispatch.php?type=deleteDataLogger&ID='+id).subscribe(response => {
      if (response['status'] == 'success') {
        alert('Deleted Successfully');
        this.getDataLogger()
      } else {
        alert('Failed: An error occured, please try again!');
      }    });

  }
}
