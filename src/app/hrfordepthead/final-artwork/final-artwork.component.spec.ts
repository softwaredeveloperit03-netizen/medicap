import { ComponentFixture, TestBed } from '@angular/core/testing';

import { FinalArtworkComponent } from './final-artwork.component';

describe('FinalArtworkComponent', () => {
  let component: FinalArtworkComponent;
  let fixture: ComponentFixture<FinalArtworkComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ FinalArtworkComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(FinalArtworkComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
