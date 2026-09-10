import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { Router } from '@angular/router';
declare let alertify: any;

@Component({
  selector: 'app-email-new',
  templateUrl: './new.component.html',
  styleUrls: ['./new.component.css']
})
export class EmailNewComponent implements OnInit {

  // Form fields
  classification: string = '';
  clientName: string = '';
  contactPerson: string = '';
  emailId: string = '';
  alternateEmail: string = '';
  phoneNumber: string = '';
  alternatePhone: string = '';
  hrRemark: string = '';
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


  saveEmail(form: any) {
    if (!form.valid) {
      alertify.error('Please fill all required fields');
      return;
    }

    const data = {
      classification: this.classification,
      client_name: this.clientName,
      contact_person: this.contactPerson,
      email_id: this.emailId,
      alternate_email: this.alternateEmail || '',
      phone_number: this.phoneNumber || '',
      alternate_phone: this.alternatePhone || '',
      hr_remark: this.hrRemark || '',
      website: this.website || '',
      country: this.country || '',
      state: this.state || '',
      place: this.place || ''
    };

    this.service.post('marketing/crm-tracking.php?type=saveEmail', data).subscribe((response: any) => {
      if (response.status === 'success') {
        alertify.success('Email tracking saved successfully');
        this.router.navigate(['/marketing/crmTracking/email/log']);
      } else {
        alertify.error(response.message || 'Error saving email tracking');
      }
    }, error => {
      console.error('Error saving email:', error);
      alertify.error('Error saving email tracking');
    });
  }

  cancel() {
    this.router.navigate(['/marketing/crmTracking']);
  }
}

 