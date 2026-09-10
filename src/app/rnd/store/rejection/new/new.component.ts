import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-new',
  templateUrl: './new.component.html',
  styleUrls: ['./new.component.css']
})
export class NewComponent implements OnInit {
  isLog = false;

  isReason = true;
  rejections;
  isInit = false;

  isNew = false;
  selectedReport = [];
  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getRejectionInformation();
  }

  showReport(index) {
    this.selectedReport = this.rejections[index];
    this.isNew = true;
  }

  checkRejectionArea(value) {
    if(value === 'No') {
      this.isReason = true;
    } else {
      this.isReason = false;
    }
  }

  getRejectionInformation() {
    this.service.get('store.php?type=getRejectionInformation').subscribe(response => {
      this.rejections = response;
    });
  }

  savereport(data) {  
    let temp = data.value;
    temp["receiving_no"] = this.selectedReport['receiving_no'];
    temp["container_no"] = this.selectedReport['container_no'];
    temp['material_code'] = this.selectedReport['material_code'];
    this.service.post('store/rejection.php?type=saveRejection', JSON.stringify(data.value))
    .subscribe(response => {
      if (response['status'] === 'success') {
        this.isNew = false;
        delete this.rejections;
        data.reset();
        this.getRejectionInformation();
        alertify.success('Rejection Report send for Approval');
      } else {
        alertify.error('An error occured');
      }
      }, (error: Response) => {
      if (error.status === 400) {
        alertify.error('An error has occurred.');
      } else {
        alertify.error('An error has occurred, http status:' + error.status);
      }
    });
  }


}
