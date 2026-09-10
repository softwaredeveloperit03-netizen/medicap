import { ComponentFixture, TestBed } from '@angular/core/testing';

import { InspfireextinguisherComponent } from './inspfireextinguisher.component';

describe('InspfireextinguisherComponent', () => {
  let component: InspfireextinguisherComponent;
  let fixture: ComponentFixture<InspfireextinguisherComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ InspfireextinguisherComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(InspfireextinguisherComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
