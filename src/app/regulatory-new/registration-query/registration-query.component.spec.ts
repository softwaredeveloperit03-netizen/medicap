import { ComponentFixture, TestBed } from '@angular/core/testing';

import { RegistrationQueryComponent } from './registration-query.component';

describe('RegistrationQueryComponent', () => {
  let component: RegistrationQueryComponent;
  let fixture: ComponentFixture<RegistrationQueryComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ RegistrationQueryComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(RegistrationQueryComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
