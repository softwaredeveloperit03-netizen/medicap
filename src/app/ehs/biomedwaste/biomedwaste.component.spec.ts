import { ComponentFixture, TestBed } from '@angular/core/testing';

import { BiomedwasteComponent } from './biomedwaste.component';

describe('BiomedwasteComponent', () => {
  let component: BiomedwasteComponent;
  let fixture: ComponentFixture<BiomedwasteComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ BiomedwasteComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(BiomedwasteComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
