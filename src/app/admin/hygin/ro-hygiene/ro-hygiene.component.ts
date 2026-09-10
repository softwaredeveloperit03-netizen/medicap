import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { Router } from '@angular/router';
declare let alertify: any;
@Component({
  selector: 'app-ro-hygiene',
  templateUrl: './ro-hygiene.component.html',
  styleUrls: ['./ro-hygiene.component.css']
})
export class RoHygieneComponent implements OnInit {

  constructor(private service: DataAccessService, private router: Router) { }

  ngOnInit(): void {
  }

  total_process_time ;
  filter_process_time;
  filter_process_end_time;

  updateTotalProcessTime() {
    const startTime = new Date(`1970-01-01T${this.filter_process_time}`);
    const endTime = new Date(`1970-01-01T${this.filter_process_end_time}`);
  
    const timeDifference = endTime.getTime() - startTime.getTime();
  
    const hours = Math.floor(timeDifference / (60 * 60 * 1000));
    const minutes = Math.floor((timeDifference % (60 * 60 * 1000)) / (60 * 1000));
  
    this.total_process_time = `${hours}:${minutes}`;
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



  save(data) {

    let temp = data.value;
    

      this.service.post('admin/housekeeping.php?type=saveRolog', JSON.stringify(temp)).subscribe(response => {
        alert("save successfully");
         data.reset();
       });
  

  }


}
