import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-market-complaints',
  templateUrl: './market-complaints.component.html',
  styleUrls: ['./market-complaints.component.css']
})
export class MarketComplaintsComponent implements OnInit {

  complaints;
  isHomepage = true;
  isInvestigationForm = false;
  isComplaintRegister = false;
  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getMarketComplaints();
  }

  getMarketComplaints() {
    this.service.get('qaDepartment.php?type=getMarketComplaints').subscribe(response => {
      this.complaints = response;
    });
  }

  savemarketComplaint(data) {
    if (!data.valid) {
      alert('All fields are required');
      return;
    }
    this.service.post('qaDepartment.php?type=savemarketComplaint', JSON.stringify(data.value)).subscribe(response => {
      if (response['status'] == "success") {
        data.resetForm();
        alert('market complaint saved successfully');
        this.getMarketComplaints();
        this.closeForm();
      } else {
        alert('An error occured, please try again');
      }
    });
  }

  checkForm(value) {
    if (value === 'register') {
      this.isHomepage = false;
      this.isComplaintRegister = true;
    } else {
      this.isHomepage = false;
      this.isInvestigationForm = true;
    }
  }

  closeForm() {
    this.isInvestigationForm = false;
    this.isComplaintRegister = false;
    this.isHomepage = true;
  }

}
