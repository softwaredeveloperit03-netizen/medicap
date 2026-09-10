import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-desktop',
  templateUrl: './desktop.component.html',
  styleUrls: ['./desktop.component.css']
})
export class DesktopComponent implements OnInit {
  
    licence_type='';
    approvel_requird = 'No';
    constructor(private service: DataAccessService,private router: Router ) {}


  ngOnInit(): void {
  }
  save(data) {
    console.log(data.value);
    if (!data.valid) {
      alert('All fields are required');
      return;
    }
    this.service.post('it/itall.php?type=savedesktopapplicationform',JSON.stringify(data.value)).subscribe(response => {
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
