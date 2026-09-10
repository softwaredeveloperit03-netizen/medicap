import { ComponentFixture, TestBed } from '@angular/core/testing';

import { LaundryRegistrationComponent } from './laundry-registration.component';

describe('LaundryRegistrationComponent', () => {
  let component: LaundryRegistrationComponent;
  let fixture: ComponentFixture<LaundryRegistrationComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ LaundryRegistrationComponent ]
    })
    .compileComponents();
  });

  beforeEach(() => {
    fixture = TestBed.createComponent(LaundryRegistrationComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
