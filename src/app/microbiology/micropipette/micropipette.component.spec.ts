import { ComponentFixture, TestBed } from '@angular/core/testing';

import { MicropipetteComponent } from './micropipette.component';

describe('MicropipetteComponent', () => {
  let component: MicropipetteComponent;
  let fixture: ComponentFixture<MicropipetteComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ MicropipetteComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(MicropipetteComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
