import { ComponentFixture, TestBed } from '@angular/core/testing';

import { GovtagencyComponent } from './govtagency.component';

describe('GovtagencyComponent', () => {
  let component: GovtagencyComponent;
  let fixture: ComponentFixture<GovtagencyComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ GovtagencyComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(GovtagencyComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
