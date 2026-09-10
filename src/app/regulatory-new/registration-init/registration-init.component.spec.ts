import { ComponentFixture, TestBed } from '@angular/core/testing';

import { RegistrationInitComponent } from './registration-init.component';

describe('RegistrationInitComponent', () => {
  let component: RegistrationInitComponent;
  let fixture: ComponentFixture<RegistrationInitComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ RegistrationInitComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(RegistrationInitComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
