import { ComponentFixture, TestBed } from '@angular/core/testing';

import { SoftenerregenrationComponent } from './softenerregenration.component';

describe('SoftenerregenrationComponent', () => {
  let component: SoftenerregenrationComponent;
  let fixture: ComponentFixture<SoftenerregenrationComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ SoftenerregenrationComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(SoftenerregenrationComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
