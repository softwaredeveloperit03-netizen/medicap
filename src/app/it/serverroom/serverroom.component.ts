import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DatePipe } from '@angular/common';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-serverroom',
  templateUrl: './serverroom.component.html',
  styleUrls: ['./serverroom.component.css']
})
export class ServerroomComponent implements OnInit {
  isNew = false;

   constructor(private service: DataAccessService) {

    }


result;
  ngOnInit(): void {
    this.getServerRoomRecord()
  }
    getServerRoomRecord() {
    this.service
      .get('it/server.php?type=getServerRoomRecord')
      .subscribe((response) => {
        this.result = response;
      });
  }
month;
location;
  addserver(data) {

    let temp = data.value;
    temp['month']=this.month
    temp['location']=this.location


          console.log('temp :>> ', temp);
          this.service.post('it/server.php?type=saveServerRoomRecord',JSON.stringify(temp)).subscribe(response => {
            if (response['status'] == 'success') {
             data.resetForm();
             this.location='';
             this.month='';
              this.getServerRoomRecord()
             alertify.success(this.service.t('common.savedSuccess'));
           } else {
             alertify.error(this.service.t('common.errorOccurred'));
           }
         });



  }

}
