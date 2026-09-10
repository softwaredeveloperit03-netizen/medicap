import { ComponentFixture, TestBed } from '@angular/core/testing';

import { EccentricitychkComponent } from './eccentricitychk.component';

describe('EccentricitychkComponent', () => {
  let component: EccentricitychkComponent;
  let fixture: ComponentFixture<EccentricitychkComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ EccentricitychkComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(EccentricitychkComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
