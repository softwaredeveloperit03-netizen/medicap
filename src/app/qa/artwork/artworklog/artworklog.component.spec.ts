import { ComponentFixture, TestBed } from '@angular/core/testing';

import { ArtworklogComponent } from './artworklog.component';

describe('ArtworklogComponent', () => {
  let component: ArtworklogComponent;
  let fixture: ComponentFixture<ArtworklogComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ ArtworklogComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(ArtworklogComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
