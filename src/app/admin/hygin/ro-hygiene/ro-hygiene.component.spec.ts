import { ComponentFixture, TestBed } from '@angular/core/testing';

import { RoHygieneComponent } from './ro-hygiene.component';

describe('RoHygieneComponent', () => {
  let component: RoHygieneComponent;
  let fixture: ComponentFixture<RoHygieneComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ RoHygieneComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(RoHygieneComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
