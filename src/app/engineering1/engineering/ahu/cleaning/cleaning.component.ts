import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-cleaning',
  templateUrl: './cleaning.component.html',
  styleUrls: ['./cleaning.component.css']
})
export class CleaningComponent implements OnInit {
  filter_type='';
  constructor(private service: DataAccessService,private router: Router ) {}


  ngOnInit(): void {
  }
  save(data) {
    console.log(data.value);
    if (!data.valid) {
      alert('All fields are required');
      return;
    }
    this.service.post('engineering/cleaning.php?type=savecleaning_seheaduleform',JSON.stringify(data.value)).subscribe(response => {
      if (response['status'] == 'success') {
        alert('Saved Successfully');
        // this.router.navigate(['/checklist']);
      } else {
        console.log(response);
        alert('Failed: An error occured, please try again!');
      }
    });
  }

}
