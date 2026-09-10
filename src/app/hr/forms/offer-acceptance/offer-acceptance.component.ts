import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-offer-acceptance',
  templateUrl: './offer-acceptance.component.html',
  styleUrls: ['./offer-acceptance.component.css']
})
export class OfferAcceptanceComponent implements OnInit {
  candidates;
  isAccepted = false;
  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getPendingAcceptanceCandidates();
  }

  checkoffer(value) {
    if(value == 'yes') {
      this.isAccepted = true;
    } else {
      this.isAccepted = false;
    }
  }

  getPendingAcceptanceCandidates() {
    this.service.get('hrDepartment.php?type=getPendingAcceptanceCandidates')
    .subscribe(response => {
      this.candidates = response;
    });
  }

  offerAcceptance(data) {
    this.service.post('hrDepartment.php?type=offerAcceptance', JSON.stringify(data.value))
    .subscribe(response => {
      if(response['status'] === 'success') {
        data.reset();
        this.getPendingAcceptanceCandidates();
      } else {
        alert('An error occured');
      }
      },
    (error: Response) => {
      if (error.status === 400) {
        alert('An error has occurred.');
      } else {
        alert('An error has occurred, http status:' + error.status);
      }
    });
  }

}
