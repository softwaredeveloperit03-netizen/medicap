import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { Router } from '@angular/router';
declare let alertify: any;



@Component({
  selector: 'app-personal-hygiene',
  templateUrl: './personal-hygiene.component.html',
  styleUrls: ['./personal-hygiene.component.css']
})
export class PersonalHygieneComponent implements OnInit {

  constructor(private service: DataAccessService, private router: Router) { }

  ngOnInit(): void {
    this.getdustsizes();
    this.getfloor();
  }

  entrydata = [];
  floor;

  addcleanData(data) {

    this.entrydata[this.entrydata.length] = data.value;
    data.resetForm();


  }

  delData(index) {
    this.entrydata.splice(index, 1);

  }

  save(data) {

    let temp = data.value;
    temp['bindata']=this.entrydata;
    

      this.service.post('admin/housekeeping.php?type=savebindata', JSON.stringify(temp)).subscribe(response => {
        alert("save successfully");
        this.entrydata.length=0;
        data.reset();
        //this.router.navigate(['/admin/vehicle-management']);
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


  add_dust_no;
  adddustno(value) {
    if (value == 'Add New') {
      this.add_dust_no = '';
      this.add_dust_no = true;
    }
  }

  save_dust_nosss;
  add_dust_nos() {
    this.service.get('admin/housekeeping.php?type=add_dustnumber&add_dust_no=' + this.save_dust_nosss).subscribe(response => {
      if (response['status'] == 'success') {
        this.getdustsizes();
        this.add_dust_no = false;

        alertify.success('Dust bin no saved successfully');

      } else {
        alertify.error(response['status']);
      }
    });

  }
  get_dust_no;
  getdustsizes() {
    this.service.get('admin/housekeeping.php?type=get_dust_nums').subscribe(response => {
      this.get_dust_no = response;
    });
    console.log(this.get_dust_no);
  }
 

  add_floor;
  add_floors(value) {
    if (value == 'Add New') {
      this.add_floor = '';
      this.add_floor = true;
    }
  }

  save_floor;
  add_floor_name() {
    this.service.get('admin/housekeeping.php?type=add_floornumber&floor=' + this.save_floor).subscribe(response => {
      if (response['status'] == 'success') {
        this.getfloor();
        this.add_floor = false;

        alertify.success('Dust bin no saved successfully');

      } else {
        alertify.error(response['status']);
      }
    });

  }
  get_fl;
  getfloor() {
    this.service.get('admin/housekeeping.php?type=get_floor_nums').subscribe(response => {
      this.get_fl = response;
    });
  }





}
