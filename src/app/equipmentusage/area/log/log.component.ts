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
  getDetails() {
  const dept = 'Store';  // ✅ CHANGE HERE

  this.service
    .get(`equipments.php?type=get_area_cleaningRecordLog&department=${dept}`)
    .subscribe({
      next: (response: any) => {
        console.log("API Response:", response); // ✅ check here
        this.datas = Array.isArray(response) ? response : [];
      },
      error: () => {
        this.datas = [];
      },
    });
}

 

   Update(id,status){
  let temp={}
  temp['id']=id;
  temp['status']=status;
  console.log(temp);
 
     this.service.post('equipments.php?type=update_equipment_usage_cleaning_record', JSON.stringify(temp)).subscribe(response => {
      const result = JSON.parse(JSON.stringify(response));
      if (result.status === 'success') {
          
        alertify.success( status + 'Successfully');
      } else {
       alertify.error(this.service.t('common.errorOccurred'));
      }
    });
}

}

