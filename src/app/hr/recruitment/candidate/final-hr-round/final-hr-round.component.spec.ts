import { ComponentFixture, TestBed } from '@angular/core/testing';

import { FinalHrRoundComponent } from './final-hr-round.component';

describe('FinalHrRoundComponent', () => {
  let component: FinalHrRoundComponent;
  let fixture: ComponentFixture<FinalHrRoundComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ FinalHrRoundComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(FinalHrRoundComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
