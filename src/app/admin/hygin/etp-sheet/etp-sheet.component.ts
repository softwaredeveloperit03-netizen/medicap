import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { Router } from '@angular/router';
declare let alertify: any;

@Component({
  selector: 'app-etp-sheet',
  templateUrl: './etp-sheet.component.html',
  styleUrls: ['./etp-sheet.component.css']
})
export class EtpSheetComponent implements OnInit {

 
  constructor(private service: DataAccessService, private router: Router) { }

  ngOnInit(): void {
    
   }

   floor;
 
 

  save(data) {

    let temp = data.value;
     


      this.service.post('admin/housekeeping.php?type=etpsave', JSON.stringify(temp)).subscribe(response => {
        alert("save successfully");
         data.reset();
       });
    
  }


  cleaning_day;
  clean_day;
  day(val) {

    let date = new Date(val);

    this.clean_day = date.getDay();
    console.log(this.clean_day);

    if (this.clean_day == 0) {
      this.cleaning_day = 'Sunday';
    } else if (this.clean_day == 1) {
      this.cleaning_day = 'Monday';

    } else if (this.clean_day == 2) {
      this.cleaning_day = 'Tuesday';

    } else if (this.clean_day == 3) {
      this.cleaning_day = 'Wednesday';

    } else if (this.clean_day == 4) {
      this.cleaning_day = 'Thursday';

    } else if (this.clean_day == 5) {
      this.cleaning_day = 'Friday';

    } else if (this.clean_day == 6) {
      this.cleaning_day = 'Saturday';

    }
    console.log(this.cleaning_day);

  }


 

   





}
