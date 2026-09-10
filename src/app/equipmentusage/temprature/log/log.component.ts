import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-log',
  templateUrl: './log.component.html',
  styleUrls: ['./log.component.css']
})
export class LogComponent implements OnInit {

  constructor(private service:DataAccessService,private router : Router) { }

  ngOnInit(): void {
this.getDetails();
  }
  datas;
 getDetails(){
  this.service.get(
    'common.php?type=getTempratureHumidityCheckingLog'
    + '&depart=' + localStorage.getItem('department')
    + '&plant_id=' + localStorage.getItem('plant_id')   // ✅ ADD THIS
  ).subscribe((response:any) => {
    console.log('API Response:', response); // 👈 ADD LOG
    this.datas = response;
  });
}

 

   Update(id,status){
  let temp={}
  temp['id']=id;
  temp['status']=status;
  console.log(temp);
 
     this.service.post('equipments.php?type=update_Temprature_humidty_record', JSON.stringify(temp)).subscribe(response => {
      const result = JSON.parse(JSON.stringify(response));
      if (result.status === 'success') {
          this.getDetails();

        alertify.success( status + 'Successfully');
      } else {
       alertify.error(this.service.t('common.errorOccurred'));
      }
    });
}

}

