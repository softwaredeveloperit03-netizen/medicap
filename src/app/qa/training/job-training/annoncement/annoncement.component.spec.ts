import { ComponentFixture, TestBed } from '@angular/core/testing';

import { AnnoncementComponent } from './annoncement.component';

describe('AnnoncementComponent', () => {
  let component: AnnoncementComponent;
  let fixture: ComponentFixture<AnnoncementComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ AnnoncementComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(AnnoncementComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
