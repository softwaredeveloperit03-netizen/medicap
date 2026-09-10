import { ComponentFixture, TestBed } from '@angular/core/testing';

import { PersonalHygieneComponent } from './personal-hygiene.component';

describe('PersonalHygieneComponent', () => {
  let component: PersonalHygieneComponent;
  let fixture: ComponentFixture<PersonalHygieneComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ PersonalHygieneComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(PersonalHygieneComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
