import { ComponentFixture, TestBed } from '@angular/core/testing';

import { RecAssestsComponent } from './rec-assests.component';

describe('RecAssestsComponent', () => {
  let component: RecAssestsComponent;
  let fixture: ComponentFixture<RecAssestsComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ RecAssestsComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(RecAssestsComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
