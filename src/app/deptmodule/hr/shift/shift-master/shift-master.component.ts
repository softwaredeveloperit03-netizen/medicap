import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-shift-master',
  templateUrl: './shift-master.component.html',
  styleUrls: ['./shift-master.component.css']
})
export class ShiftMasterComponent implements OnInit {
save(_t26: any) {
throw new Error('Method not implemented.');
}

  holidays;
  isNewHoliday= false;
  constructor(private service: DataAccessService) {
   }

  ngOnInit() {
    this.getHolidays();
  }

  getHolidays() {
    this.service.get('hr/shift.php?type=get_shift_list')
    .subscribe(response => {
      this.holidays = response;
    });
  } 

  addHolidays(qualificationForm) {
   
    this.service.post('hr/shift.php?type=save_shift', JSON.stringify(qualificationForm.value))
    .subscribe(response => {
      if(response['status']=='success'){
        qualificationForm.reset();
        this.getHolidays();
        this.isNewHoliday = false;
        alertify.success("save successfully")
      }else{
        alertify.error(response['status'])
      }
      },
    (error: Response) => {
      if (error.status === 400) {
        alertify.error('An error has occurred.');
      } else {
        alertify.error('An error has occurred, http status:' + error.status);
      }
    });
  }


}
