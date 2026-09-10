import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { Router } from '@angular/router';
declare let alertify: any;
@Component({
  selector: 'app-scrap',
  templateUrl: './scrap.component.html',
  styleUrls: ['./scrap.component.css']
})
export class ScrapComponent implements OnInit {

  constructor(private service: DataAccessService, private router: Router) { }

  ngOnInit(): void {
    this.getscrap();
  }


  savescrap(data) {

    let temp = data.value;
     

      this.service.post('admin/housekeeping.php?type=save_scrap', JSON.stringify(temp)).subscribe(response => {
        alert("save successfully");
         data.reset();
       });
   


}

scrap_rate_kg;
 total_amount =0;

addition(val){

  this.total_amount = this.scrap_rate_kg * val;
  console.log(this.scrap_rate_kg);
  console.log(val);
  console.log(this.total_amount);

}

add_scrap;
add_scraps(value) {
  if (value == 'Add New') {
    this.add_scrap = '';
    this.add_scrap = true;
  }
}

save_scrap;
add_scrap_type() {
  this.service.get('admin/housekeeping.php?type=scrap_types&save_scrap=' + this.save_scrap).subscribe(response => {
    if (response['status'] == 'success') {
      this.getscrap();
      this.add_scrap = false;

      alertify.success('Dust bin no saved successfully');

    } else {
      alertify.error(response['status']);
    }
  });

}
get_fl;
getscrap() {
  this.service.get('admin/housekeeping.php?type=get_scrap_types').subscribe(response => {
    this.get_fl = response;
  });
}




}