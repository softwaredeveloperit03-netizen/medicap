import { ComponentFixture, TestBed } from '@angular/core/testing';

import { NitrogenlogComponent } from './nitrogenlog.component';

describe('NitrogenlogComponent', () => {
  let component: NitrogenlogComponent;
  let fixture: ComponentFixture<NitrogenlogComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ NitrogenlogComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(NitrogenlogComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
