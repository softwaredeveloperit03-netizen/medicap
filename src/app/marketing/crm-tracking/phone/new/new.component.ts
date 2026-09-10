import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { Router } from '@angular/router';
declare let alertify: any;

@Component({
  selector: 'app-phone-new',
  templateUrl: './new.component.html',
  styleUrls: ['./new.component.css']
})
export class PhoneNewComponent implements OnInit {

  // Basic Info (Stage 1)
  classification: string = '';
  clientName: string = '';
  contactPerson: string = '';
  phoneNumber: string = '';
  emailId: string = '';
  website: string = '';
  country: string = '';
  state: string = '';
  place: string = '';

  // Dropdown data
  countries: string[] = [];

  constructor(private service: DataAccessService, private router: Router) { }

  ngOnInit(): void {
    this.getCountries();
  }

  getCountries() {
    this.countries = [
      'Afghanistan', 'Albania', 'Algeria', 'Andorra', 'Angola', 'Antigua and Barbuda', 'Argentina', 'Armenia', 'Australia', 'Austria',
      'Azerbaijan', 'Bahamas', 'Bahrain', 'Bangladesh', 'Barbados', 'Belarus', 'Belgium', 'Belize', 'Benin', 'Bhutan',
      'Bolivia', 'Bosnia and Herzegovina', 'Botswana', 'Brazil', 'Brunei', 'Bulgaria', 'Burkina Faso', 'Burundi', 'Cabo Verde', 'Cambodia',
      'Cameroon', 'Canada', 'Central African Republic', 'Chad', 'Chile', 'China', 'Colombia', 'Comoros', 'Congo, Democratic Republic of the', 'Congo, Republic of the',
      'Costa Rica', 'Croatia', 'Cuba', 'Cyprus', 'Czech Republic', 'Denmark', 'Djibouti', 'Dominica', 'Dominican Republic', 'Ecuador',
      'Egypt', 'El Salvador', 'Equatorial Guinea', 'Eritrea', 'Estonia', 'Eswatini', 'Ethiopia', 'Fiji', 'Finland', 'France',
      'Gabon', 'Gambia', 'Georgia', 'Germany', 'Ghana', 'Greece', 'Grenada', 'Guatemala', 'Guinea', 'Guinea-Bissau',
      'Guyana', 'Haiti', 'Honduras', 'Hungary', 'Iceland', 'India', 'Indonesia', 'Iran', 'Iraq', 'Ireland',
      'Israel', 'Italy', 'Jamaica', 'Japan', 'Jordan', 'Kazakhstan', 'Kenya', 'Kiribati', 'Kuwait', 'Kyrgyzstan',
      'Laos', 'Latvia', 'Lebanon', 'Lesotho', 'Liberia', 'Libya', 'Liechtenstein', 'Lithuania', 'Luxembourg', 'Madagascar',
      'Malawi', 'Malaysia', 'Maldives', 'Mali', 'Malta', 'Marshall Islands', 'Mauritania', 'Mauritius', 'Mexico', 'Micronesia',
      'Moldova', 'Monaco', 'Mongolia', 'Montenegro', 'Morocco', 'Mozambique', 'Myanmar', 'Namibia', 'Nauru', 'Nepal',
      'Netherlands', 'New Zealand', 'Nicaragua', 'Niger', 'Nigeria', 'North Korea', 'North Macedonia', 'Norway', 'Oman', 'Pakistan',
      'Palau', 'Panama', 'Papua New Guinea', 'Paraguay', 'Peru', 'Philippines', 'Poland', 'Portugal', 'Qatar', 'Romania',
      'Russia', 'Rwanda', 'Saint Kitts and Nevis', 'Saint Lucia', 'Saint Vincent and the Grenadines', 'Samoa', 'San Marino', 'Sao Tome and Principe', 'Saudi Arabia', 'Senegal',
      'Serbia', 'Seychelles', 'Sierra Leone', 'Singapore', 'Slovakia', 'Slovenia', 'Solomon Islands', 'Somalia', 'South Africa', 'South Korea',
      'South Sudan', 'Spain', 'Sri Lanka', 'Sudan', 'Suriname', 'Sweden', 'Switzerland', 'Syria', 'Taiwan', 'Tajikistan',
      'Tanzania', 'Thailand', 'Timor-Leste', 'Togo', 'Tonga', 'Trinidad and Tobago', 'Tunisia', 'Turkey', 'Turkmenistan', 'Tuvalu',
      'Uganda', 'Ukraine', 'United Arab Emirates', 'United Kingdom', 'United States', 'Uruguay', 'Uzbekistan', 'Vanuatu', 'Vatican City', 'Venezuela',
      'Vietnam', 'Yemen', 'Zambia', 'Zimbabwe'
    ];
  }

  saveBasicInfo(form: any) {
    if (!form.valid) {
      alertify.error('Please fill all required fields');
      return;
    }

    if (!this.classification || !this.clientName || !this.contactPerson || !this.phoneNumber) {
      alertify.error('Please fill all required fields');
      return;
    }

    const data = {
      classification: this.classification,
      client_name: this.clientName,
      contact_person: this.contactPerson,
      phone_number: this.phoneNumber,
      email_id: this.emailId || '',
      website: this.website || '',
      country: this.country || '',
      state: this.state || '',
      place: this.place || ''
    };

    this.service.post('marketing/crm-tracking.php?type=savePhoneCall', data).subscribe((response: any) => {
      if (response.status === 'success') {
        alertify.success('Basic information saved successfully');
        // Navigate to update component with the saved ID
        if (response.id) {
          this.router.navigate(['/marketing/crmTracking/phone/update', response.id]);
        } else {
          // If ID not returned, navigate to log and user can update from there
          this.router.navigate(['/marketing/crmTracking/phone/log']);
        }
      } else {
        alertify.error(response.message || 'Error saving basic information');
      }
    }, error => {
      console.error('Error saving phone call:', error);
      alertify.error('Error saving basic information');
    });
  }

  cancel() {
    this.router.navigate(['/marketing/crmTracking']);
  }
}
